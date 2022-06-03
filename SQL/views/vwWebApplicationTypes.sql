USE [LGS_UAT]
GO

/****** Object:  View [dbo].[vwWebApplicationTypes]    Script Date: 3/06/2022 2:15:08 PM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

ALTER View [dbo].[vwWebApplicationTypes]
as

	SELECT ApplicationTypes.* 
	FROM ApplicationTypes
	
;
GO


