USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebGetEntityDetails]    Script Date: 1/07/2022 11:47:54 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Achira Warnakulasuriya>
-- Create date: <20/10/2021>
-- Description:	<get Entity ID based on Ed_name1, ET_Code,ED_Surname,ED_DOB,ED_TrusteeName and ED_ABN>
-- =============================================
ALTER PROCEDURE [dbo].[spWebGetEntityDetails]
	-- Add the parameters for the stored procedure here
	@ED_Name1		VARCHAR(5) =NULL,
	@ED_Surname		VARCHAR(50) = NULL,
	@ENT_DOB		smalldatetime = NULL,
	@ET_Code		VARCHAR(5) = NULL,
	@ED_ABN			VARCHAR(100) = NULL,
	@ED_TrusteeName VARCHAR(255) = NULL
AS

SET NOCOUNT ON;
	
BEGIN
	
	DECLARE @TEMP_ENT_ID NUMERIC(18,0) = NULL
		
	IF @ET_Code = 'P' or @ET_Code = 'CO' or @ET_Code = 'IN' or @ET_Code = 'OT' BEGIN
		IF @ED_ABN IS NOT NULL BEGIN
			IF @ED_TrusteeName IS NULL BEGIN
				SELECT top 1 @TEMP_ENT_ID = ED_ENT_ID FROM EntityDetails WHERE ED_ABN = @ED_ABN AND ED_TrusteeName IS NULL ORDER BY ED_ID DESC;
			END
			ELSE IF @ED_TrusteeName IS NOT NULL BEGIN
				SELECT top 1 @TEMP_ENT_ID = ED_ENT_ID FROM EntityDetails WHERE ED_ABN = @ED_ABN AND LOWER(ED_TrusteeName) = @ED_TrusteeName ORDER BY ED_ID DESC;
			END
		END
		IF @TEMP_ENT_ID IS NULL BEGIN
			IF @ET_Code = 'P' BEGIN
				SELECT Top 1 @TEMP_ENT_ID = dbo.Entities.ENT_ID 
				FROM    dbo.Entities LEFT OUTER JOIN
						dbo.EntityTypes ON dbo.Entities.ENT_ET_ID = dbo.EntityTypes.ET_ID INNER JOIN
						dbo.EntityDetails ON dbo.Entities.ENT_ED_ID = dbo.EntityDetails.ED_ID AND dbo.Entities.ENT_ID = dbo.EntityDetails.ED_ENT_ID
						WHERE LOWER(LEFT(ED_Name1, 1)) = @ED_Name1 AND  LOWER(ED_Surname) = @ED_Surname AND ENT_DOB = @ENT_DOB
						AND ET_Code = @ET_Code AND LOWER(ED_TrusteeName) = @ED_TrusteeName
						ORDER BY ENT_ID DESC				
			END
			ELSE IF @ET_Code = 'CO' or @ET_Code = 'IN' or @ET_Code = 'OT' BEGIN
				SELECT Top 1 @TEMP_ENT_ID = dbo.Entities.ENT_ID 
				FROM    dbo.Entities LEFT OUTER JOIN
						dbo.EntityTypes ON dbo.Entities.ENT_ET_ID = dbo.EntityTypes.ET_ID INNER JOIN
						dbo.EntityDetails ON dbo.Entities.ENT_ED_ID = dbo.EntityDetails.ED_ID AND dbo.Entities.ENT_ID = dbo.EntityDetails.ED_ENT_ID
						WHERE LOWER(LEFT(ED_Name1, 1)) = @ED_Name1
						AND ET_Code = @ET_Code AND LOWER(ED_TrusteeName) = @ED_TrusteeName
						ORDER BY ENT_ID DESC			
			END
		END
	END

	SELECT @TEMP_ENT_ID as ENT_ID
END
