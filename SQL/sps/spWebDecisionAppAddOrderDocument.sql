USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebDecisionAppAddOrderDocument]    Script Date: 3/06/2022 2:32:01 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/*
+--------------------------------------------------------------------------------------------
| FUNCTION:  
| HISTORY:
| DATE			WHO      		DESCRIPTION OF CHANGE
| -------------------------------------------------------------------------------------------
| 15/08/2019 	DWDEN			Add an order or limited licence PDF to the Documents table
+--------------------------------------------------------------------------------------------*/
--grant execute on [spWebDecisionAppAddOrderDocument] to webuser
ALTER PROCEDURE [dbo].[spWebDecisionAppAddOrderDocument]
@AOR_ID numeric(18,0),
@DOC_File varchar(max) = null,
@AU_Name varchar(16) = null,
@iApply varchar(10) = null
AS

BEGIN
	SET NOCOUNT ON
	

	IF @iApply is not null BEGIN
		DECLARE @DOC_File_decoded varbinary(max) = null
		SET @DOC_File_decoded = cast('' as xml).value('xs:base64Binary(sql:variable("@DOC_File"))', 'varbinary(max)')
		INSERT INTO Documents (DOC_AOR_ID, DOC_DT_ID, DOC_File, DOC_FileName, CreationDateTime, Creationuser, LastUpdateDateTime, LastUpdateUser)
		VALUES (
			@AOR_ID,
			(select DT_ID from DocumentTypes where DT_Code = 'ORD'),
			@DOC_File_decoded,
			'Order.pdf',
			getdate(),
			@AU_Name,
			getdate(),
			@AU_Name
		)
	END ELSE BEGIN
		INSERT INTO Documents (DOC_AOR_ID, DOC_DT_ID, DOC_File, DOC_FileName, CreationDateTime, Creationuser, LastUpdateDateTime, LastUpdateUser)
		VALUES (
			@AOR_ID,
			(select DT_ID from DocumentTypes where DT_Code = 'ORD'),
			@DOC_File,
			'Order.pdf',
			getdate(),
			@AU_Name,
			getdate(),
			@AU_Name
		)
	END

	select GUID, DOC_ID from Documents where DOC_ID = SCOPE_IDENTITY()
END

